# Icon maker
Icon maker

## Add required Icons
Add the following icons in your `projectRoot/art/images/icon-maker`
```php
'favicon.png - required size 1024x1024',
'icon.png - required size 1024x1024',
'icon-maskable.png - required size 1024x1024',
'logo.png - PNG format Logo',
'logo.svg - SVG Logo',
'logo-sm.png - PNG format Logo small - just icon only',
'logo-sm.svg - SVG Logo small - just icon only',
'apple-tab-icon.svg - SVG Logo',
'splash.png - required size 8064 x 8064. We will resize it for all splash screens' // optional
```

## Run command to create icons
In project root rOun following commands to create icons
```shell
icon-maker make:icon # to create icons
icon-maker make:icon --splash # to create icons with splash
icon-maker make:icon --splash --optimize # to create icons with splash
```
It will create following content or files
- icons in `public/assets/icons`
- head.html at `public/assets/icons/head.html`
- manifest.json at `public/manifest.json`
- manifest.webmanifest at `public/manifest.webmanifest`

## Config (Optional)
Add `icon-maker.json` file in root of your project. All are optional.
```json
{
    "configThemeLocation": "config/theme.php",
    "webManifestLocation": "public/manifest.webmanifest",
    "manifestJsonLocation": "public/manifest.json",
    "headHtmlLocation": "resources/views/common/head.blade.php",
    "headHtmlBladeLocation": "resources/views/common/head.blade.php",
    "destinationDirectory": "public/assets/icons"
}
```
