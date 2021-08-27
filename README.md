# Map Campaign page (map-campaign)

## About project

This is page for campaigns focused on the closest things searched by user location. You can test it on: [stagging.oddajubrania.pl](https://stagging.oddajubrania.pl/), Access to page: L:oddajubrania P:stagging.

You can run this project on http server with PHP (for local run use fe. xampp). Alternatively you can mock ReCaptcha & Mapbox responses from captcha.php.

## Project preparations

1. Sign up and create API token for Production and Develop on [mapbox.com](https://www.mapbox.com/).
2. Sign up website on Google ReCaptcha v3 for Production and Develop.
3. Create file `config.php` in `/public` directory with these constants:
```php
<?php
define('CAPTCHA_PUBLIC' , 'PROD RECAPTCHA PUBLIC HERE');
define('CAPTCHA_SECRET' , 'PROD RECAPTCHA SECRET HERE');
define('MAPBOX_TOKEN' , 'PROD MAPBOX TOKEN HERE');

define('DEV_CAPTCHA_PUBLIC' , 'DEV RECAPTCHA PUBLIC HERE');
define('DEV_CAPTCHA_SECRET' , 'DEV RECAPTCHA SECRET HERE');
define('DEV_MAPBOX_TOKEN' , 'DEV MAPBOX TOKEN HERE');
?>
```
4. Prepare your locations by replacing content in `/src/assets/markers.js` in this way:
```javascript
export const markers = [
    {
        lat: 50.00623618,   // geo latitude (important for location features)
        lng: 20.01935774,   // geo longitude (important for location features)
        name: 'Shop example', 
        id: 2765, 
        city: 'Krakow', 
        postalCode: '30-864',
        address: 'Mała Góra 97 str.',
        phone: '+48 784 042 765' }, 
    // etc.
]
```

## Project run

```
yarn install
```

### Compiles and hot-reloads for development

```
yarn serve
```

### Compiles and minifies for production

```
yarn build
```

### Run your unit tests (I will add tests in the future :-))

```
yarn test:unit
```

### Lints and fixes files

```
yarn lint
```

### Customize configuration

See [Configuration Reference](https://cli.vuejs.org/config/).
