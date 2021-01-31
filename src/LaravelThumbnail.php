<?php

namespace HungNM\LaravelThumbnail;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class LaravelThumbnail
{

    /**
     * Generate thumbnail image
     *
     * @param $image
     * @param null $width
     * @param null $height
     * @param string $type fit - best fit possible for given width & height - by default | resize - exact resize of image | background - fit image perfectly keeping ratio and adding black background | resizeCanvas - keep only center
     * @return mixed
     */
    public static function generate($image, $width = null, $height = null, $type = 'fit'): string
    {
        // Return default image if empty
		if (empty($image)) {
			return Storage::disk('public')->url(config('thumb.default_img'));
		}

        // Configuration path
        $rootPath = config('thumb.root_path');
        $thumbPath = config('thumb.thumb_path');

        // Remove the first path (symlink) from $image string
        $image = ltrim(substr($image, strpos($image, '/', 1)), '/');

        // remove extension and add png extension
        $imageFilename = pathinfo($image, PATHINFO_FILENAME) . '.png';

        // Thumbnail file
        $thumbnail = $rootPath . $thumbPath . $width . 'x' . $height . '_' . $type . '/' . $imageFilename;

        // Thumbnail full path
        $thumbnailFileFullPath = storage_path('app/public' . $thumbnail);

        /**
         * Thumbnail exists, we will return it
         */

        if (File::exists($thumbnailFileFullPath)) {
            return Storage::disk('public')->url($thumbnail);
        }

        /**
         * Thumbnail does not exist, we will create it
         */

        // Image file full path
        $imageFileFullPath = storage_path('app/public' . $rootPath . $image);

        // if current image exists and it is an image
        if (File::exists($imageFileFullPath) && !File::isDirectory($imageFileFullPath)) {

            $allowedMimeTypes = ['image/jpeg', 'image/gif', 'image/png'];
            $contentType = mime_content_type($imageFileFullPath);

            // Check mimetypes
            if (in_array($contentType, $allowedMimeTypes)) {
                // returns the original image if no width and height
                if (is_null($width) && is_null($height)) {
                    return Storage::disk('public')->url($image);
                }

                // if thumbnail do not exist, we make it
                $image = Image::make($imageFileFullPath);

                switch ($type) {
                    case 'fit':
                    {
                        $image->fit($width, $height, function ($constraint) {
                        });
                        break;
                    }
                    case 'resize':
                    {
                        // stretched
                        $image->resize($width, $height);
                    }
                    case 'background':
                    {
                        $image->resize($width, $height, function ($constraint) {
                            // keeps aspect ratio and sets black background
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }
                    case 'resizeCanvas':
                    {
                        $image->resizeCanvas($width, $height, 'center', false, 'rgba(0, 0, 0, 0)'); // gets the center part
                    }
                }

                // Create the directory if it doesn't exist
                $thumbnailPath = storage_path('app/public' . dirname($thumbnail));

                if (!File::exists($thumbnailPath)) {
                    File::makeDirectory($thumbnailPath, 0775, true);
                }

                // Save the thumbnail, encoded as png
                $image->save($thumbnailFileFullPath);

                // return the url of the thumbnail
                return Storage::disk('public')->url($thumbnail);
            } else {
                // Return original image, if mimetypes is not defined
                return $image;
            }
        } else {
            // if image does not exist and return a default image
            return Storage::disk('public')->url(config('thumb.default_img'));
        }
    }
}
