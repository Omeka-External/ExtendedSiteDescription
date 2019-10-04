# Extended Site Description
## A module for Omeka S

This module currently creates 3 additional inputs in the Settings page for every Site:

- Image: an "asset" uploader/picker for attaching an image to a site
- Linear: a true/false value controlled by a checkbox
- Categories: a multi-select of categories the site belongs to. Categories to choose from
  are configured in the module's Configure page.

In a public view, these values are accessibile using the siteSetting helper:

```php
$linear = $this->siteSetting('extended_site_description_linear');

$linear = $this->siteSetting('extended_site_description_categories');

$imageUrl = '';
$imageId = $this->siteSetting('extended_site_description_image');
if ($imageId) {
    try {
        $response = $this->api()->read('assets', $imageId);
        $imageUrl = $reponse->getContent()->assetUrl();
    } catch (\Omeka\Api\Exception\NotFoundException $e) {}
}
```


