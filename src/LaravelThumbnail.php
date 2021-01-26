<?php

namespace HungNM\LaravelThumbnail;

use Illuminate\Support\Facades\File;
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
    public static function generate($image, $width = null, $height = null, $type = 'fit')
    {
        $rootPath = config('thumb.root_path');
        $thumbPath = config('thumb.thumbs_path');

        $image = ltrim(substr($image, strpos($image, '/', 1)), '/');
        $imagePublicPath = storage_path('app/public' . $rootPath . $image);

        //if path exists and is image
        if (File::exists($imagePublicPath) && !File::isDirectory($imagePublicPath)) {

            $allowedMimeTypes = ['image/jpeg', 'image/gif', 'image/png'];
            $contentType = mime_content_type($imagePublicPath);

            if (in_array($contentType, $allowedMimeTypes)) {
                //returns the original image if no width and height
                if (is_null($width) && is_null($height)) {
                    return asset($image);
                }

                //remove extension and add png extension
                $imageFilename = pathinfo($image, PATHINFO_FILENAME) . '.png';

                //if thumbnail exist returns it
                $thumbnail = $rootPath . '/' . $thumbPath . $width . 'x' . $height . '_' . $type . '/' . $imageFilename;

                $thumbnailPublicPath = storage_path('app/public' . $thumbnail);

                if (File::exists($thumbnailPublicPath)) {
                    return asset($thumbnail);
                }

                // if thumbnail do not exist, we make it
                $image = Image::make($imagePublicPath);

                switch ($type) {
                    case 'fit':
                    {
                        $image->fit($width, $height, function ($constraint) {
                        });
                        break;
                    }
                    case 'resize':
                    {
                        //stretched
                        $image->resize($width, $height);
                    }
                    case 'background':
                    {
                        $image->resize($width, $height, function ($constraint) {
                            //keeps aspect ratio and sets black background
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }
                    case 'resizeCanvas':
                    {
                        $image->resizeCanvas($width, $height, 'center', false, 'rgba(0, 0, 0, 0)'); //gets the center part
                    }
                }

                //Create the directory if it doesn't exist
                $thumbnailPublicDir = storage_path('app/public' . (dirname($thumbnail) === '.') ? '' : dirname($thumbnail));

                if (!File::exists($thumbnailPublicDir)) {
                    File::makeDirectory($thumbnailPublicDir, 0775, true);
                }

                //Save the thumbnail, encoded as png
                $image->save($thumbnailPublicPath);

                //return the url of the thumbnail
                return asset($thumbnail);

            } else {
                return asset(config('thumb.default_img'));
            }
        } else {
            return asset(config('thumb.default_img'));
        }
    }
}
