<?php

namespace DevFelipeReis\UploadTrait;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This trait was built to ez upload files based on a path set in your model
 *  Use it with your model like: $model->uploadFile($someFile)
 *
 *  Use getBaseUploadFolderPath function to set the base path on your model.
 *
 *  @author Felipe Reis <feelipereis7@gmail.com>
 */
trait UploadTrait
{
    /**
     * Check if we are in production or not
     * @return Boolean
     */
    private function isProduction() {
        // check if we are in production env or not .. "staging" is also here because is like a production env but for testing
        return App::environment(['production', 'staging']);
    }

    /**
     * This method needs to return a path where your file will be uploaded
     */
    abstract function getBaseUploadFolderPath();

    /**
     * An array of strings matching the attributes that will have upload paths
     *
     *  This will be used to delete all uploads from a model when
     *      the model is removed from database and you don't want to keep that data
     *
     * @var array An array of strings
     */
    protected $uploadableAttributes = [];

    /**
     * Control if you want to delete the last uploaded file before upload a new one
     *  TODO: The logic for this
     *
     * @var boolean
     */
    protected $keepLastUploadedFile = false;

    /**
     * The method responsible to upload the file to the right location
     *
     * @param UploadedFile $file File to be uploaded
     * @param String $path the path where the file will be uploaded... if path is not set, getBaseUploadFolderPath() will be called
     * @return String The file path in the filesystem... Save it to the database
     */
    public function uploadFile(UploadedFile $file, $path = null)
    {
        $path = $this->prepareFilePath($path);

        // if production, send to S3 bucket
        if ($this->isProduction()) {
            return $this->uploadToS3($file, $path);
        }

        //----- we are in dev, upload to local storage

        // creates the file path
        $storage_path = strtr(':path:fileName.:fileExtension', [
            ':path' => $path,
            ':fileName' => md5($file->getClientOriginalName()),
            ':fileExtension' => $file->getClientOriginalExtension()
        ]);

        $real_path = file_get_contents($file->getRealPath()); // get the uploaded file and...
        Storage::put($storage_path, $real_path); // save it to the storage

        return $storage_path;
    }

    /**
     * It will prepare the file path(the base path for the file) based on the environment
     *  Dev and production envs have some differences, plz check the code below
     *
     * @param String $filePath
     * @return String
     */
    private function prepareFilePath($filePath) {

        if (!$filePath) {
            $filePath = $this->getBaseUploadFolderPath();
        }

        if ($this->isProduction()) {
            $filePath = rtrim($filePath, '/');
        }

        return $filePath;
    }

    /**
     * It will upload a file to the s3 bucket defined in the env file
     *
     * @param UploadedFile $file
     * @param String $filePath
     * @return String
     */
    private function uploadToS3($file, $filePath) {
        return $file->store($filePath, 's3');
    }

    /**
     * It will build a URL to serve your file
     *
     * @param String $filePath The path generated when uploaded was made
     * @return String
     */
    private function buildUploadURL($filePath) {
        if ($this->isProduction()) {
            return Storage::cloud()->url($filePath);
        }
        return url('storage/?path=' . $filePath);
    }

    /**
     * It will generate a URL for your model attribute
     *
     * @param String $attribute The attribute of your model that has a upload file path
     * @return String A url for your file. Use this method in your views
     */
    public function getUploadUrlFor($attribute) {
        $attributeValue = $this->selectAttributeValue($attribute);
        if (empty($attributeValue)) {
            return ''; // Idk what to do here yet. To not break your app, It will just return a empty string
        }
        return $this->buildUploadURL($attributeValue);
    }

    /**
     * It will delete the upload file related to an attribute
     *
     * @param Array $something The attribute or an array of attributes of your model that has a upload file path
     * @return Boolean
     */
    public function deleteUploadFor($something) {

        if (empty($something)) throw new \Exception("deleteUploadFor needs something to delete. Check the parameter.", 1);

        if (is_array($something)) {
            $toBeDeleted = array_map($this->selectAttributeValue, $something);
        } else {
            $toBeDeleted = $this->selectAttributeValue($something);
        }

        if ($this->isProduction()) {
            return Storage::cloud()->delete($toBeDeleted);
        }
        return Storage::delete($toBeDeleted);
    }

    /**
     * Delete all uploads from a model
     * You need to define the attributes used to keep upload paths in $uploadableAttributes
     *
     * @return Boolean
     */
    public function deleteAllUploads() {
        if (empty($this->uploadableAttributes)) throw new \Exception("Can not delete any file. uploadableAttributes is empty.", 1);

        return $this->deleteUploadFor($this->uploadableAttributes);
    }

    /**
     * It will return the value of the attribute passed as parameter
     *
     * @param String $attributeName
     * @return mixed
     */
    private function selectAttributeValue($attributeName) {
        return $this->$attributeName;
    }
}
