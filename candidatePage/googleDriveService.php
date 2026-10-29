<?php
require_once __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;

class GoogleDriveService {
    private $service;
    private $folderId;

    public function __construct($accessToken = null) {
        $client = new Client();
        
        if ($accessToken) {
            // Use OAuth 2.0 token from user sign-in
            $client->setAccessToken($accessToken);
            
            // Check if token is expired and try to refresh if possible
            if ($client->isAccessTokenExpired()) {
                try {
                    // Try to refresh using the refresh token if available
                    $refreshToken = $client->getRefreshToken();
                    if ($refreshToken) {
                        $client->fetchAccessTokenWithRefreshToken($refreshToken);
                        // Update the session with new access token
                        $_SESSION['access_token'] = $client->getAccessToken();
                    } else {
                        // No refresh token available, we'll proceed and let it fail naturally
                        // The API call might still work or give a clear error
                    }
                } catch (Exception $e) {
                    // If refresh fails, we'll proceed with the expired token
                    // and let the Drive API call handle the error
                    error_log("Token refresh failed: " . $e->getMessage());
                }
            }
        } else {
            throw new Exception('No OAuth access token provided');
        }
        
        $this->service = new Drive($client);
        $this->folderId = '17h5scvfFVtQHOqYJoxbGhOaG1jnNSF-N';
    }

    /**
     * Get the Drive service instance (for external access)
     */
    public function getService() {
        return $this->service;
    }

    /**
     * Upload file to Google Drive
     */
    public function uploadFile($filePath, $fileName, $mimeType, $parentFolderId = null) {
        try {
            // FIX: Use the correct namespaced class
            $fileMetadata = new DriveFile([
                'name' => $fileName
            ]);
            
            // Add parent folder if provided
            if ($parentFolderId) {
                $fileMetadata->setParents([$parentFolderId]);
            }
            
            $content = file_get_contents($filePath);
            
            $file = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id, name, webViewLink, webContentLink'
            ]);
            
            return [
                'success' => true,
                'file_id' => $file->id,
                'file_name' => $file->name,
                'web_view_link' => $file->webViewLink,
                'web_content_link' => $file->webContentLink
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete file from Google Drive
     */
    public function deleteFile($fileId) {
        try {
            $this->service->files->delete($fileId);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate public URL for file
     */
    public function makeFilePublic($fileId) {
        try {
            $permission = new Permission([
                'type' => 'anyone',
                'role' => 'reader'
            ]);

            $this->service->permissions->create($fileId, $permission);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get file download URL
     */
    public function getFileUrl($fileId) {
        try {
            $file = $this->service->files->get($fileId, ['fields' => 'webContentLink']);
            return $file->webContentLink;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get file view URL
     */
    public function getFileViewUrl($fileId) {
        try {
            $file = $this->service->files->get($fileId, ['fields' => 'webViewLink']);
            return $file->webViewLink;
        } catch (Exception $e) {
            return null;
        }
    }
}
?>