<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class VideoService
{
    private string $videoUploadDirectory;
    private string $thumbnailUploadDirectory;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger,
        string $kernelProjectDir
    ) {
        $this->videoUploadDirectory = $kernelProjectDir . '/public/uploads/videos/';
        $this->thumbnailUploadDirectory = $kernelProjectDir . '/public/uploads/thumbnails/';
        
        // Create directories if they don't exist
        if (!is_dir($this->videoUploadDirectory)) {
            mkdir($this->videoUploadDirectory, 0777, true);
        }
        if (!is_dir($this->thumbnailUploadDirectory)) {
            mkdir($this->thumbnailUploadDirectory, 0777, true);
        }
    }

    // ... rest of the methods remain the same ...
}