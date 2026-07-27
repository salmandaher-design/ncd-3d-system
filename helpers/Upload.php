<?php
/**
 * Secure file upload helper (validation + storage).
 */
class Upload
{
    /**
     * Handle a single project image.
     * Returns the stored relative path (uploads/images/xxx) or null on no file.
     * Throws RuntimeException on validation failure.
     */
    public static function image(array $file): ?string
    {
        if (!isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        self::checkError($file);

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            throw new RuntimeException('Image exceeds the maximum size of ' . MAX_UPLOAD_MB . ' MB.');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_IMAGE_EXT, true)) {
            throw new RuntimeException('Invalid image type. Allowed: ' . implode(', ', ALLOWED_IMAGE_EXT) . '.');
        }

        // Confirm it is really an image.
        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            throw new RuntimeException('The uploaded image is not valid.');
        }

        self::ensureDir(IMAGES_DIR);
        $name = self::randomName($ext);
        $dest = IMAGES_DIR . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Failed to store the uploaded image.');
        }
        return 'uploads/images/' . $name;
    }

    /**
     * Handle multiple project files from a `name="files[]"` field.
     * Returns an array of ['original','path','size','type'].
     */
    public static function files(array $filesField): array
    {
        $stored = [];
        if (!isset($filesField['name'])) {
            return $stored;
        }

        // Normalise to an array even for a single file.
        $names = (array) $filesField['name'];
        $count = count($names);

        for ($i = 0; $i < $count; $i++) {
            $error = $filesField['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $one = [
                'name'     => $filesField['name'][$i],
                'type'     => $filesField['type'][$i] ?? '',
                'tmp_name' => $filesField['tmp_name'][$i],
                'error'    => $error,
                'size'     => $filesField['size'][$i] ?? 0,
            ];
            self::checkError($one);

            if ($one['size'] > MAX_UPLOAD_SIZE) {
                throw new RuntimeException('"' . $one['name'] . '" exceeds the maximum size of ' . MAX_UPLOAD_MB . ' MB.');
            }

            $ext = strtolower(pathinfo($one['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_FILE_EXT, true)) {
                throw new RuntimeException('"' . $one['name'] . '" has an invalid type. Allowed: ' . implode(', ', ALLOWED_FILE_EXT) . '.');
            }

            self::ensureDir(FILES_DIR);
            $storedName = self::randomName($ext);
            $dest = FILES_DIR . '/' . $storedName;
            if (!move_uploaded_file($one['tmp_name'], $dest)) {
                throw new RuntimeException('Failed to store "' . $one['name'] . '".');
            }

            $stored[] = [
                'original' => $one['name'],
                'path'     => 'uploads/files/' . $storedName,
                'size'     => (int) $one['size'],
                'type'     => $ext,
            ];
        }
        return $stored;
    }

    /** Delete a stored file given its relative path. */
    public static function remove(?string $relPath): void
    {
        if (!$relPath) return;
        $full = UPLOAD_DIR . '/' . str_replace('uploads/', '', $relPath);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private static function checkError(array $file): void
    {
        $err = $file['error'] ?? UPLOAD_ERR_OK;
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('A file is too large for the server limit (' . MAX_UPLOAD_MB . ' MB).');
        }
        if ($err !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File upload failed (error code ' . $err . ').');
        }
    }

    private static function randomName(string $ext): string
    {
        return date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    }

    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}
