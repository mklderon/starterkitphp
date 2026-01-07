<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Clase PulseFile
 * Manejo de archivos y uploads
 * 
 * @version 2.0.0
 */
class PulseFile
{
    private const MAX_FILE_SIZE = 10485760; // 10MB
    private const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    private const ALLOWED_DOCUMENT_TYPES = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];

    private $errors = [];
    private $uploadPath;

    /**
     * Constructor
     * 
     * @param string|null $uploadPath Ruta donde se guardarán los archivos
     */
    public function __construct(?string $uploadPath = null)
    {
        // En XAMPP/Apache con rewrite a public/, los archivos deben estar en public/uploads
        // O en una carpeta permitida. La mejor práctica es public/uploads.
        $this->uploadPath = $uploadPath ?? (defined('UPLOADS_PATH') ? UPLOADS_PATH : PROJECTROOT . 'public' . DS . 'uploads' . DS);
        $this->ensureUploadDirectory();
    }

    /**
     * Sube una imagen
     * 
     * @param string $fieldName Nombre del campo del formulario
     * @param array $options Opciones adicionales
     * @return array|false ['success' => bool, 'filename' => string, 'path' => string]
     */
    public function uploadImage(string $fieldName, array $options = [])
    {
        $this->errors = [];

        // Validar que existe el archivo
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            $this->errors[] = "No se ha seleccionado ningún archivo.";
            return false;
        }

        $file = $_FILES[$fieldName];

        // Validar errores de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadError($file['error']);
            return false;
        }

        // Validar que sea imagen real
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $this->errors[] = "El archivo no es una imagen válida.";
            return false;
        }

        // Validar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = $options['allowed_types'] ?? self::ALLOWED_IMAGE_TYPES;

        if (!in_array($extension, $allowedTypes)) {
            $this->errors[] = "Tipo de archivo no permitido. Permitidos: " . implode(', ', $allowedTypes);
            return false;
        }

        // Validar tamaño
        $maxSize = $options['max_size'] ?? self::MAX_FILE_SIZE;
        if ($file['size'] > $maxSize) {
            $this->errors[] = "El archivo es demasiado grande. Máximo: " . $this->formatBytes($maxSize);
            return false;
        }

        // Generar nombre único
        $filename = $this->generateUniqueFilename($extension, $options['prefix'] ?? '');
        $targetPath = $this->uploadPath . $filename;

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->errors[] = "Error al guardar el archivo.";
            return false;
        }

        // Comprimir si se solicitó
        if ($options['compress'] ?? false) {
            $this->compressImage($targetPath, $options['quality'] ?? 85);
        }

        // Redimensionar si se solicitó
        if (isset($options['max_width']) || isset($options['max_height'])) {
            $this->resizeImage(
                $targetPath,
                $options['max_width'] ?? null,
                $options['max_height'] ?? null
            );
        }

        return [
            'success' => true,
            'filename' => $filename,
            'relative_path' => 'uploads/' . $filename,
            'path' => $targetPath,
            'url' => $this->getFileUrl($filename)
        ];
    }

    /**
     * Sube múltiples imágenes
     * 
     * @param string $fieldName
     * @param array $options
     * @return array
     */
    public function uploadMultipleImages(string $fieldName, array $options = []): array
    {
        $results = [];

        if (!isset($_FILES[$fieldName])) {
            return ['success' => false, 'error' => 'No se encontró el campo'];
        }

        $files = $this->normalizeFilesArray($_FILES[$fieldName]);

        foreach ($files as $index => $file) {
            $_FILES['temp_upload'] = $file;
            $result = $this->uploadImage('temp_upload', $options);

            if ($result) {
                $results[] = $result;
            } else {
                $results[] = [
                    'success' => false,
                    'index' => $index,
                    'errors' => $this->errors
                ];
            }
        }

        return $results;
    }

    /**
     * Sube un documento
     * 
     * @param string $fieldName
     * @param array $options
     * @return array|false
     */
    public function uploadDocument(string $fieldName, array $options = [])
    {
        $this->errors = [];

        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            $this->errors[] = "No se ha seleccionado ningún archivo.";
            return false;
        }

        $file = $_FILES[$fieldName];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadError($file['error']);
            return false;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = $options['allowed_types'] ?? self::ALLOWED_DOCUMENT_TYPES;

        if (!in_array($extension, $allowedTypes)) {
            $this->errors[] = "Tipo de archivo no permitido.";
            return false;
        }

        $maxSize = $options['max_size'] ?? self::MAX_FILE_SIZE;
        if ($file['size'] > $maxSize) {
            $this->errors[] = "El archivo es demasiado grande.";
            return false;
        }

        $filename = $this->generateUniqueFilename($extension, $options['prefix'] ?? '');
        $targetPath = $this->uploadPath . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->errors[] = "Error al guardar el archivo.";
            return false;
        }

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $targetPath,
            'url' => $this->getFileUrl($filename)
        ];
    }

    /**
     * Elimina un archivo
     * 
     * @param string $filename
     * @return bool
     */
    public function deleteFile(string $filename): bool
    {
        $filepath = $this->uploadPath . $filename;

        if (!file_exists($filepath)) {
            $this->errors[] = "El archivo no existe.";
            return false;
        }

        if (!unlink($filepath)) {
            $this->errors[] = "No se pudo eliminar el archivo.";
            return false;
        }

        return true;
    }

    /**
     * Comprime una imagen
     * 
     * @param string $filepath
     * @param int $quality 0-100
     * @return bool
     */
    private function compressImage(string $filepath, int $quality = 85): bool
    {
        $imageInfo = getimagesize($filepath);
        if (!$imageInfo)
            return false;

        $mimeType = $imageInfo['mime'];

        // Cargar imagen según tipo
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($filepath);
                imagejpeg($image, $filepath, $quality);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filepath);
                imagepng($image, $filepath, (int) (9 - ($quality / 11)));
                break;
            case 'image/gif':
                return true; // GIF no se comprime
            case 'image/webp':
                $image = imagecreatefromwebp($filepath);
                imagewebp($image, $filepath, $quality);
                break;
            default:
                return false;
        }

        if (isset($image)) {
            imagedestroy($image);
        }

        return true;
    }

    /**
     * Redimensiona una imagen manteniendo proporción
     * 
     * @param string $filepath
     * @param int|null $maxWidth
     * @param int|null $maxHeight
     * @return bool
     */
    private function resizeImage(string $filepath, ?int $maxWidth, ?int $maxHeight): bool
    {
        $imageInfo = getimagesize($filepath);
        if (!$imageInfo)
            return false;

        list($width, $height) = $imageInfo;
        $mimeType = $imageInfo['mime'];

        // Calcular nuevas dimensiones
        $ratio = $width / $height;

        if ($maxWidth && $maxHeight) {
            if ($width / $maxWidth > $height / $maxHeight) {
                $newWidth = $maxWidth;
                $newHeight = (int) ($maxWidth / $ratio);
            } else {
                $newHeight = $maxHeight;
                $newWidth = (int) ($maxHeight * $ratio);
            }
        } elseif ($maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) ($maxWidth / $ratio);
        } elseif ($maxHeight) {
            $newHeight = $maxHeight;
            $newWidth = (int) ($maxHeight * $ratio);
        } else {
            return false;
        }

        // No redimensionar si es más pequeña
        if ($newWidth >= $width && $newHeight >= $height) {
            return true;
        }

        // Crear nueva imagen
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Cargar imagen original
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($filepath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($filepath);
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($filepath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($filepath);
                break;
            default:
                return false;
        }

        // Redimensionar
        imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Guardar
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($newImage, $filepath, 90);
                break;
            case 'image/png':
                imagepng($newImage, $filepath);
                break;
            case 'image/gif':
                imagegif($newImage, $filepath);
                break;
            case 'image/webp':
                imagewebp($newImage, $filepath);
                break;
        }

        imagedestroy($source);
        imagedestroy($newImage);

        return true;
    }

    /**
     * Genera un nombre de archivo único
     */
    private function generateUniqueFilename(string $extension, string $prefix = ''): string
    {
        return $prefix . uniqid() . '_' . time() . '.' . $extension;
    }

    /**
     * Normaliza el array de archivos múltiples
     */
    private function normalizeFilesArray(array $files): array
    {
        $normalized = [];

        // Soporte para upload simple (name="file") vs array (name="file[]")
        if (!is_array($files['name'])) {
            return [$files];
        }

        foreach ($files['name'] as $index => $name) {
            // Ignorar entradas vacías si las hubiera
            if (empty($name))
                continue;

            $normalized[] = [
                'name' => $files['name'][$index],
                'type' => $files['type'][$index],
                'tmp_name' => $files['tmp_name'][$index],
                'error' => $files['error'][$index],
                'size' => $files['size'][$index]
            ];
        }

        return $normalized;
    }

    /**
     * Obtiene el mensaje de error de upload
     */
    private function getUploadError(int $code): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido.',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario.',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente.',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en disco.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.'
        ];

        return $errors[$code] ?? 'Error desconocido al subir el archivo.';
    }

    /**
     * Formatea bytes a formato legible
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Asegura que exista el directorio de uploads
     */
    private function ensureUploadDirectory(): void
    {
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Obtiene la URL del archivo
     */
    private function getFileUrl(string $filename): string
    {
        // Como los archivos ahora viven en public/uploads, y el Rewrite redirige todo a public/
        // La URL final debe ser http://dominio/uploads/archivo.png
        // Asumiendo que URL_PATH ya incluye el dominio y subcarpeta (ej: http://localhost/salud/)
        return URL_PATH . 'uploads/' . $filename;
    }

    /**
     * Obtiene los errores
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtiene el último error
     */
    public function getLastError(): ?string
    {
        return end($this->errors) ?: null;
    }
}