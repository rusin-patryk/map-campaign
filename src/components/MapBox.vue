<template>
    <div class="mapbox-container">
        <div class="mapbox-form">
            <label>
                Podaj kod pocztowy
                <input autofocus v-model="formData.postalCode" placeholder="XX-XXX" v-mask="'##-###'" />
            </label>
            <label>
                lub nazwę miejscowości
                <input v-model="formData.city" />
            </label>

            <button class="search" type="button" @click="searchLocation(formData.postalCode, formData.city)">Szukaj</button>

            <span class="my-location-button" @click="getUserLocation">Użyj mojej lokalizacji</span>
            <LocationPicker :locations="locations" @pickLocation="centerOnLocation" @close="closePicker" />
        </div>
        <div class="map">
            <div v-if="!token" class="lds-ring"><div></div><div></div><div></div><div></div></div>
            <LeafletMap
                v-if="token"
                :zoom="zoom"
                :center="center"
                :markers="markers"
                :picked-location="pickedLocation"
                :geojson="geojson"
                :token="token"
                :geojson-visible="geojsonVisible"
                :fly-to-obj="flyToObj"
            />
        </div>
    </div>
</template>

<script>
import { geoJson, latLngBounds } from 'leaflet';
import LeafletMap from '@/components/LeafletMap';
import LocationPicker from '@/components/LocationPicker';
import leafletKnn from 'leaflet-knn';
import { markers } from '@/assets/markers';
import { mask } from 'vue-the-mask';
import axios from 'axios';

export default {
    name: 'MapBox',
    directives: {mask},
    components: {
        LeafletMap,
        LocationPicker,
    },
    data() {
        return {
            zoom: 6,
            formData: {
                postalCode: '',
                city: '',
            },
            center: [52.1302243, 19.3478346],
            pickedLocation: [],
            token: '',
            markers: markers,
            locations: [],
            geojson: null,
            geojsonVisible: false,
            flyToObj: {
                bounds: null,
                options: {
                    padding: [60, 60],
                    maxZoom: 15,
                    animate: false,
                },
            },
        };
    },

    computed: {
        getGeoJSONData() {
            const points = [];
            markers.forEach((element) => {
                points.push({
                    type: "Point",
                    coordinates: [element.lat, element.lng],
                    properties: {
                        name: `${ element.name } - ${ element.address }, ${ element.postalCode } ${ element.city }`,
                    },
                });
            });

            return geoJson(points);
        },
    },

    beforeCreate() {
        window.grecaptcha.ready(() => {
            window.grecaptcha.execute('6Lfk3tsbAAAAAC08lFSMnQL8ry4g1zSbz68HPu2M', {action: 'submit'}).then((token) => {
                axios.get(`https://stagging.oddajubrania.pl/captcha.php?token=${ token }`)
                    .then(response => {
                        this.token = response.data;
                    });
            });
        });
    },

    methods: {
        searchLocation(postalCode, city) {
            if (postalCode || city) {
                let requestUrl = 'https://api.mapbox.com/geocoding/v5/mapbox.places/';
                if (postalCode) {
                    requestUrl += postalCode.replace(' ', '') + '.json?country=PL&types=postcode&limit=10&' + this.token;
                } else if (city) {
                    requestUrl += city.replace(' ', '%20') + '.json?country=PL&language=pl&&types=place&limit=10&' + this.token;
                }
                this.pickedLocation = [];
                axios.get(requestUrl)
                    .then(response => {
                        this.locations = response.data.features;
                        if (this.locations.length === 1) {
                            this.centerOnLocation(this.locations[0]);
                        }
                    });
            }
        },

        getUserLocation() {
            navigator.geolocation.getCurrentPosition((location) => {
                this.getAddress([location.coords.longitude, location.coords.latitude]);
            }, () => {
                alert('Błąd! Nie udało się pobrać lokalizacji.');
            }, {enableHighAccuracy: true});
        },

        centerOnLocation(location) {
            this.emitLocation(location);
            const latLng = [location.center[1], location.center[0]];
            this.pickedLocation = latLng;
            const closest = leafletKnn(this.getGeoJSONData).nearest(latLng, 2);
            if (location.place_type[0] === 'postcode' || location.place_type[0] === 'geolocation') {
                this.flyToObj.bounds = latLngBounds(latLng, this.getKnnLatLng(closest[0]));
                this.flyToObj.options.maxZoom = 15;
            } else {
                this.flyToObj.bounds = latLngBounds([latLng, this.getKnnLatLng(closest[0]), this.getKnnLatLng(closest[1])]);
                this.flyToObj.options.maxZoom = 14;
            }

            this.searchRoute(latLng, closest[0]);
            this.locations = [];
        },

        searchRoute(latLng, closest) {
            const requestUrl = `https://api.mapbox.com/directions/v5/mapbox/driving/${ latLng[1].toFixed(4) },${ latLng[0].toFixed(4) };${ this.getKnnLatLng(closest)[1] },${ this.getKnnLatLng(closest)[0] }?geometries=geojson&access_token=pk.eyJ1IjoibmFub2l0IiwiYSI6ImNrcnZ2cW42ejBhZXQybm44ZXdnenRzbGsifQ.29MhI2aCgJMB93atv6eGtQ`;
            this.geojsonVisible = false;
            axios.get(requestUrl)
                .then(response => {
                    this.geojson = response.data.routes[0].geometry;
                    this.geojsonVisible = true;
                    this.emitRoute(response.data.routes[0], closest);
                });
        },

        getAddress(location) {
            let requestUrl = `https://api.mapbox.com/geocoding/v5/mapbox.places/${ location[0].toFixed(4) },${ location[1].toFixed(4) }.json?${ this.token }`;
            axios.get(requestUrl)
                .then(response => {
                    this.centerOnLocation({
                        center: location,
                        place_type: ['geolocation'],
                        place_name: response.data.features[0].place_name,
                    });
                });
        },

        emitLocation(location) {
            let placeType = '';
            if (location.place_type[0] === 'postcode') {
                placeType = ' po kodzie pocztowym';
            } else if (location.place_type[0] === 'place') {
                placeType = ' po nazwie miejscowości';
            }
            this.$emit('pickedLocation', `Znaleziona lokalizacja${ placeType }: ${ location.place_name_pl ? location.place_name_pl : location.place_name }`);
        },

        emitRoute(route, closest) {
            this.$emit('closestPoint', {
                name: `Najbliższy sklep to ${ closest.layer.feature.geometry.properties.name } w odległości ${ Math.round(route.distance / 100) / 10 } km (około ${ Math.round(route.duration / 60) } min. drogi samochodem).`,
                latLng: this.getKnnLatLng(closest),
            });
        },

        getKnnLatLng(pointObj) {
            return [pointObj.lon, pointObj.lat];
        },

        closePicker() {
            this.locations = [];
        },
    },

    beforeDestroy() {
        const cookies = document.cookie.split(";");

        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i];
            const eqPos = cookie.indexOf("=");
            const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
            document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
        }
    },
};
</script>

<style lang="scss" scoped>
@import '../styles/_MapBox.scss';
</style>
