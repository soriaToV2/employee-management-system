<?php
require_once 'googleDriveService.php';
require '../connection.php';

class FileUploadHandler {
    private $driveService;
    private $conn;
    private $parentFolderId = '17h5scvfFVtQHOqYJoxbGhOaG1jnNSF-N'; // Your main folder ID
    
    // Define the existing subfolder IDs for different document types
    private $subfolderIds = [
        'Resume' => '1guSOOvzx_rf53SHSGvFv1HQvkrIWWc95',
        'Government ID' => '10v5BldoZ4NVg15QMh0NzzHrh8t0GM3rz',
        'Birth Certificate' => '1UGXE_bmtUe0OncGzZMwGAxlYe_K8lZei',
        'Diploma' => '19OGYblJ2vnt2Nnr6D32DLiBfnu05SKAd',
        'Certificate' => '12yGy6aTXq7iERzrcViPpvPrxK0LD2OWZ'
    ];

    public function __construct($conn, $accessToken = null) {
        if (!$accessToken) {
            throw new Exception('OAuth access token required for file uploads');
        }
        $this->driveService = new GoogleDriveService($accessToken);
        $this->conn = $conn;
    }

    /**
     * Handle document upload for candidates with subfolder organization
     */
    public function uploadCandidateDocument($candidateId, $file, $documentType) {
        $uploadDir = __DIR__ . '/uploads/temp/';
        
        // Create temp directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
            'application/pdf', 'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }

        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'error' => 'File too large. Maximum size is 5MB'];
        }

        // Generate unique filename
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $documentType . '_' . $candidateId . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $tempFilePath = $uploadDir . $fileName;

        // Move uploaded file to temp directory
        if (!move_uploaded_file($file['tmp_name'], $tempFilePath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }

        try {
            // Use the predefined subfolder ID, or main folder if not specified
            $targetFolderId = $this->subfolderIds[$documentType] ?? $this->parentFolderId;

            // Upload to Google Drive with the specific subfolder
            $driveResult = $this->driveService->uploadFile(
                $tempFilePath, 
                $fileName, 
                $file['type'],
                $targetFolderId
            );

            if (!$driveResult['success']) {
                unlink($tempFilePath); // Clean up temp file
                return $driveResult;
            }

            // Make file publicly accessible
            $publicResult = $this->driveService->makeFilePublic($driveResult['file_id']);
            if (!$publicResult['success']) {
                error_log("Failed to make file public: " . $publicResult['error']);
            }

            // Use the webViewLink for viewing URL
            $fileUrl = $driveResult['web_view_link'] ?? $driveResult['web_content_link'] ?? '';

            // Store in database
            $query = "INSERT INTO documents (candidate_id, document_type, file_link, google_drive_id) 
                      VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param($stmt, "isss", $candidateId, $documentType, $fileUrl, $driveResult['file_id']);
            
            if (mysqli_stmt_execute($stmt)) {
                $documentId = mysqli_insert_id($this->conn);
                
                // Clean up temp file
                unlink($tempFilePath);
                
                return [
                    'success' => true,
                    'document_id' => $documentId,
                    'file_url' => $fileUrl,
                    'google_drive_id' => $driveResult['file_id']
                ];
            } else {
                // Rollback: Delete from Google Drive if DB insert fails
                $this->driveService->deleteFile($driveResult['file_id']);
                unlink($tempFilePath);
                return ['success' => false, 'error' => 'Database insertion failed'];
            }

        } catch (Exception $e) {
            // Clean up temp file
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle certificate file upload specifically for certifications table
     */
    public function uploadCertificateFile($candidateId, $file, $certificateName) {
        $uploadDir = __DIR__ . '/uploads/temp/';
        
        // Create temp directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedTypes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
            'application/pdf', 'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }

        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'error' => 'File too large. Maximum size is 5MB'];
        }

        // Generate unique filename
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'Certificate_' . $candidateId . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $tempFilePath = $uploadDir . $fileName;

        // Move uploaded file to temp directory
        if (!move_uploaded_file($file['tmp_name'], $tempFilePath)) {
            return ['success' => false, 'error' => 'Failed to move uploaded file'];
        }

        try {
            // Use the predefined Certificates folder ID
            $targetFolderId = $this->subfolderIds['Certificate'] ?? $this->parentFolderId;

            // Upload to Google Drive with the specific subfolder
            $driveResult = $this->driveService->uploadFile(
                $tempFilePath, 
                $fileName, 
                $file['type'],
                $targetFolderId
            );

            if (!$driveResult['success']) {
                unlink($tempFilePath); // Clean up temp file
                return $driveResult;
            }

            // Make file publicly accessible
            $publicResult = $this->driveService->makeFilePublic($driveResult['file_id']);
            if (!$publicResult['success']) {
                error_log("Failed to make file public: " . $publicResult['error']);
            }

            // Use the webViewLink for viewing URL
            $fileUrl = $driveResult['web_view_link'] ?? $driveResult['web_content_link'] ?? '';

            // Clean up temp file
            unlink($tempFilePath);
            
            return [
                'success' => true,
                'file_url' => $fileUrl,
                'google_drive_id' => $driveResult['file_id']
            ];

        } catch (Exception $e) {
            // Clean up temp file
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle multiple file uploads with subfolder organization
     */
    public function uploadMultipleDocuments($candidateId, $files) {
        $results = [];
        
        foreach ($files as $documentType => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $result = $this->uploadCandidateDocument($candidateId, $file, $documentType);
                $results[$documentType] = $result;
            }
        }
        
        return $results;
    }

    /**
     * Delete document
     */
    public function deleteDocument($documentId) {
        // Get Google Drive file ID
        $query = "SELECT google_drive_id FROM documents WHERE document_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $documentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $document = mysqli_fetch_assoc($result);

        if ($document) {
            // Delete from Google Drive
            $driveResult = $this->driveService->deleteFile($document['google_drive_id']);
            
            // Delete from database
            $deleteQuery = "DELETE FROM documents WHERE document_id = ?";
            $deleteStmt = mysqli_prepare($this->conn, $deleteQuery);
            mysqli_stmt_bind_param($deleteStmt, "i", $documentId);
            $dbResult = mysqli_stmt_execute($deleteStmt);

            return [
                'drive_success' => $driveResult['success'],
                'db_success' => $dbResult
            ];
        }

        return ['success' => false, 'error' => 'Document not found'];
    }
}
?>