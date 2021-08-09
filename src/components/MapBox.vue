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

            <button class="search"
                    type="button"
                    @click="searchLocation(formData.postalCode, formData.city)">Znajdź na mapie
            </button>

            <span class="my-location-button" @click="getUserLocation">Użyj mojej lokalizacji</span>
            <LocationPicker :locations="locations" @pickLocation="centerOnLocation" @close="closePicker" />
        </div>
        <div class="map" id="map-anchor" @touchstart="onTwoFingerDrag" @touchend="onTwoFingerDrag">
            <div v-if="!token && !error" class="lds-ring">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
            <LeafletMap
                v-if="token && !error"
                :zoom="zoom"
                :center="center"
                :markers="markers"
                :picked-location="pickedLocation"
                :geojson="geojson"
                :token="token"
                :geojson-visible="geojsonVisible"
                :fly-to-obj="flyToObj"
                :pc="pc"
            />
            <div v-if="error">
                <span v-if="typeof error === 'string'">
                    {{ error }}
                </span>
                <span v-else>
                    Błąd: nie powiodła się weryfikacja Google ReCaptcha - mapa niedostępna. Spróbuj odświeżyć stronę.
                </span>
            </div>
        </div>
        <notifications position="bottom left" width="340px" />
    </div>
</template>

<script>
import Vue from 'vue';
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
            markers: markers,
            locations: [],
            geojson: null,
            geojsonVisible: false,
            token: '',
            error: false,
            flyToObj: {
                bounds: null,
                options: {
                    padding: [60, 60],
                    maxZoom: 15,
                    animate: false,
                },
            },
            pc: true,
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

    created() {
        this.runRecaptcha();

        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            this.pc = false;
        }
    },

    methods: {
        searchLocation(postalCode, city) {
            if (postalCode || city) {
                let requestUrl = 'https://api.mapbox.com/geocoding/v5/mapbox.places/';
                if (postalCode) {
                    if (postalCode.length !== 6) {
                        Vue.notify({
                            type: 'error',
                            title: 'Błąd!',
                            text: 'Wprowadź poprawny kod pocztowy.',
                            duration: 7000,
                        });
                        return;
                    }
                    requestUrl += postalCode.replace(' ', '') + '.json?country=PL&types=postcode&limit=10&' + this.token;
                } else if (city) {
                    if (!city.length > 2) {
                        Vue.notify({
                            type: 'error',
                            title: 'Błąd!',
                            text: 'Wprowadź poprawną miejscowość.',
                            duration: 7000,
                        });
                        return;
                    }
                    requestUrl += city.replace(' ', '%20') + '.json?country=PL&language=pl&&types=place&limit=10&' + this.token;
                }
                this.pickedLocation = [];
                axios.get(requestUrl)
                    .then(response => {
                        if (!response.data.features.length) {
                            Vue.notify({
                                type: 'error',
                                title: 'Błąd!',
                                text: 'Nie udało się znaleźć wprowadzonej lokalizacji.',
                                duration: 7000,
                            });
                        } else {
                            Vue.notify({
                                type: 'success',
                                title: 'OK!',
                                text: 'Znaleźliśmy wyszukiwaną lokalizację.',
                            });
                            this.locations = response.data.features;
                            if (this.locations.length === 1) {
                                this.centerOnLocation(this.locations[0]);
                            }
                        }
                    })
                    .catch(() => {
                        Vue.notify({
                            type: 'error',
                            title: 'Błąd!',
                            text: 'Nie udało się znaleźć wprowadzonej lokalizacji.',
                            duration: 7000,
                        });
                    });
            } else {
                Vue.notify({
                    type: 'warn',
                    title: 'Błąd!',
                    text: 'Nie wypełniono formularza.',
                    duration: 7000,
                });
            }
        },

        getUserLocation() {
            navigator.geolocation.getCurrentPosition((location) => {
                this.getAddress([location.coords.longitude, location.coords.latitude]);
                Vue.notify({
                    type: `${ location.coords.accuracy < 200 ? 'success' : 'warn' }`,
                    title: `${ location.coords.accuracy < 200 ? 'OK!' : 'OK, ale...' }`,
                    text: `Znaleźliśmy ${ location.coords.accuracy < 200 ? 'Twoją dokładną lokalizację.' : 'niedokładną lokalizację - Twoja realna pozycja może być inna.' }`,
                    duration: 7000,
                });
            }, () => {
                Vue.notify({
                    type: 'error',
                    title: 'Błąd!',
                    text: 'Prawdopodobnie Twoje urządzenie nie zezwoliło na udostępnienie lokalizacji. Użyj formularza.',
                    duration: 7000,
                });
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
                })
                .finally(() => {
                    setTimeout(() => {
                        window.location.href = '#map-anchor';
                    }, 100);
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

        onTwoFingerDrag(e) {
            if (e.type === 'touchstart' && e.touches.length === 1) {
                e.currentTarget.classList.add('swiping');
                this.pc = true;
            } else {
                e.currentTarget.classList.remove('swiping');
                this.pc = false;
            }
        },

        runRecaptcha(tries = 0) {
            axios.get(`${ window.location.origin }/captcha.php?public`)
                .then((response) => {
                    if (response.data.data && response.data.data.indexOf('error') === -1) {
                        this.executeRecaptcha(response.data.data);
                    } else if (tries < 3) {
                        setTimeout(() => {
                            this.runRecaptcha(++tries);
                        }, 250)
                    } else {
                        console.error('ReCaptcha execute error');
                        Vue.notify({
                            type: 'error',
                            title: 'Błąd!',
                            text: 'Nie udało się załadować Google ReCaptcha. Spróbuj odświeżyć stronę.',
                            duration: 100000,
                        });
                        this.error = true;
                    }
                })
                .catch(() => {
                    if (tries < 3) {
                        setTimeout(() => {
                            this.runRecaptcha(++tries);
                        }, 250)
                    } else {
                        console.error('ReCaptcha execute error');
                        Vue.notify({
                            type: 'error',
                            title: 'Błąd!',
                            text: 'Nie udało się załadować Google ReCaptcha. Spróbuj odświeżyć stronę.',
                            duration: 100000,
                        });
                        this.error = true;
                    }
                })
        },

        executeRecaptcha(captchaPublicKey, tries = 0) {
            if (window.grecaptcha) {
                window.grecaptcha.ready(() => {
                    window.grecaptcha.execute(captchaPublicKey, {action: 'submit'}).then((token) => {
                        axios.get(`${ window.location.origin }/captcha.php?token=${ token }`)
                            .then(response => {
                                if (response.data.data === 'error:bot') {
                                    this.error = 'Błąd: nie można było załadować mapy, ponieważ wykryto niestandardowe zachowanie Twojego urządzenia (wykryto spam lub robota). Jeśli jednak nie jesteś robotem, spróbuj odświeżyć stronę.';
                                    Vue.notify({
                                        type: 'error',
                                        title: 'Błąd!',
                                        text: 'Nie można było załadować mapy, ponieważ wykryto niestandardowe zachowanie Twojego urządzenia (wykryto spam lub robota). Jeśli jednak nie jesteś robotem, spróbuj odświeżyć stronę.',
                                        duration: 100000,
                                    });
                                } else if (!response.data.data || response.data.data.split(':')[0] === 'error') {
                                    if (tries < 10) {
                                        setTimeout(() => {
                                            this.executeRecaptcha(captchaPublicKey, ++tries);
                                        }, 250);
                                    } else {
                                        console.error('ReCaptcha verification error');
                                        Vue.notify({
                                            type: 'error',
                                            title: 'Błąd!',
                                            text: 'Nie powiodła się weryfikacja Google ReCaptcha - mapa niedostępna. Spróbuj odświeżyć stronę.',
                                            duration: 100000,
                                        });
                                        this.error = true;
                                    }
                                } else {
                                    this.error = false;
                                    this.token = 'access_token=' + response.data.data;
                                }
                            })
                            .catch(() => {
                                if (tries < 10) {
                                    setTimeout(() => {
                                        this.executeRecaptcha(captchaPublicKey, ++tries);
                                    }, 500);
                                } else {
                                    console.error('ReCaptcha verification error');
                                    Vue.notify({
                                        type: 'error',
                                        title: 'Błąd!',
                                        text: 'Nie powiodła się weryfikacja Google ReCaptcha - mapa niedostępna. Spróbuj odświeżyć stronę.',
                                        duration: 100000,
                                    });
                                    this.error = true;
                                }
                            });
                    });
                });
            } else if (tries < 10) {
                setTimeout(() => {
                    this.executeRecaptcha(captchaPublicKey, ++tries);
                }, 500);
            } else {
                console.error('ReCaptcha execute error');
                Vue.notify({
                    type: 'error',
                    title: 'Błąd!',
                    text: 'Nie udało się załadować Google ReCaptcha. Spróbuj odświeżyć stronę.',
                    duration: 100000,
                });
                this.error = true;
            }
        }
    },
};
</script>

<style lang="scss" scoped>
@import '../styles/_MapBox.scss';
</style>
