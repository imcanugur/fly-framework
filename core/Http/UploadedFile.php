<?php

declare(strict_types=1);

namespace Fly\Http;

/**
 * Represents a file uploaded via HTTP request.
 *
 * Wraps the PHP $_FILES entry and provides a clean API
 * for validation, inspection, and movement.
 */
class UploadedFile
{
    public function __construct(
        protected readonly string $path,
        protected readonly string $originalName,
        protected readonly string $mimeType,
        protected readonly int    $size,
        protected readonly int    $error,
    ) {}

    /**
     * Get the temporary file path.
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Get the original file name from the client.
     */
    public function originalName(): string
    {
        return $this->originalName;
    }

    /**
     * Get the file extension based on the original name.
     */
    public function extension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    /**
     * Get the MIME type reported by the client.
     */
    public function mimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get the detected MIME type via finfo (more reliable than client-reported).
     */
    public function detectedMimeType(): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($this->path) ?: 'application/octet-stream';
    }

    /**
     * Get the file size in bytes.
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * Get the upload error code.
     */
    public function error(): int
    {
        return $this->error;
    }

    /**
     * Determine if the file was uploaded successfully.
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->path);
    }

    /**
     * Move the uploaded file to a new location.
     */
    public function moveTo(string $destination): bool
    {
        $dir = dirname($destination);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return move_uploaded_file($this->path, $destination);
    }

    /**
     * Get the file contents.
     */
    public function contents(): string
    {
        return (string) file_get_contents($this->path);
    }

    /**
     * Get a human-readable error message.
     */
    public function errorMessage(): string
    {
        return match ($this->error) {
            UPLOAD_ERR_OK         => 'No error.',
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize directive.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'Upload stopped by a PHP extension.',
            default               => 'Unknown upload error.',
        };
    }
}
