# Simple upload trait for Laravel
A simple upload trait for laravel.
It will upload to the local storage if in development and to a s3 bucket if in production/staging.

For production, you need to follow laravel documentation to set s3 as your default cloud disk.

Just tested with Laravel 5.4. But it should work with Laravel 5.x.

Note: This is something made for my setup and works good. I like storage for dev and s3 for production. Feel free to change whatever you like to meet your needs ;)

### How to install

```
composer require devfelipereis/uploadtrait
```

Now see the example below to understand how to use it.


# Example

In your model, set the base path for your uploads:

```php
...
use DevFelipeReis\UploadTrait\UploadTrait;

class Company extends Model
{
    use UploadTrait;

    ...

    public function getBaseUploadFolderPath() {
        return 'companies/' . $this->id . '/';
    }
}
```

Now in your controller...

```php
public function store(CreateCompanyRequest $request)
{
    ...
    $inputs = $request->except('logo');
    $company = $this->companyRepository->create($inputs);

    // Company logo
    $company_logo = $request->file('logo');
    if ($company_logo) {
        $company->logo = $company->uploadFile($company_logo);
        // $company->logo will be something like: companies/1/8e5dc57cb5d80532f52e13597c5f0b68.jpg
    }

    $company->save();

    ...
}
```

### How to access the image?

For dev, I like to create a route like this:

**Note:** This route will only be used for dev. For production, a full url for the file in your s3 bucket will be used.

```php
Route::get('/storage', ['as' => 'uploaded-file', 'uses' => function() {
	$path = $_GET['path'];
	if (!$path) {
	    abort(404);
	}
	return response()->file( storage_path('app/') . $path);
}]);
```

Finally, inside the view:

```html
...
<img id="logo-img" class="thumbnail preview-img" src="{{ $company->getUploadUrlFor('logo') }}"/>
...
```

### How to delete the image?

Maybe you want to delete that image, try this:

```php
    $company->deleteUploadFor('photo');
}]);
```
