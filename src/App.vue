<template>
    <div id="app" class="app-wrapper">
        <div class="header-container">
            <div class="container">
                <img draggable="false" src="@/assets/logo.png" alt="logo" style="height: 130px">
            </div>
        </div>
        <div class="content-container">
            <div class="container">
                <div class="page-title">
                    <div class="page-title-image">
                        <img src="@/assets/kontener.png" alt="biedronka">
                    </div>
                    <div>
                        Wyszukaj najbliższy sklep <img src="@/assets/biedronka.png" alt="biedronka"><br>
                        z naszym kontenerem<br>
                        i oddaj ubrania w których już nie chodzisz
                    </div>
                </div>
                <div class="map-section">
                    <MapBox @pickedLocation="getPickedLocation" @closestPoint="getClosestPoint" />
                    <div class="map-section-tips">
                        <div v-if="!pickedLocation && !closestPoint.name">
                            Wyszukaj lokalizację poprzez formularz po lewej stronie lub użyj swojej lokalizacji, aby znaleźć najbliższe sklepy z naszym kontenerem.
                        </div>
                        <div v-if="pickedLocation">
                            {{ pickedLocation }}
                        </div>
                        <div v-if="closestPoint.name">
                            {{ closestPoint.name }}
                            <a :href="`https://www.google.com/maps/dir//${closestPoint.latLng[0]},${closestPoint.latLng[1]}`"
                               target="_blank">Nawiguj</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-container">
            <div class="container">
                <a href="/Polityka_prywatnosci-cookies_oddajubrania.pl.pdf" target="_blank">Polityka prywatności oraz pliki cookies</a>
            </div>
        </div>
    </div>
</template>

<script>
import MapBox from './components/MapBox.vue';

export default {
    name: 'App',
    components: {
        MapBox,
    },
    data() {
        return {
            pickedLocation: '',
            closestPoint: {
                name: '',
                latLng: null,
            }
        }
    },

    methods: {
        getPickedLocation(data) {
            this.pickedLocation = data;
        },

        getClosestPoint(data) {
            this.closestPoint = data;
        }
    }
};
</script>

<style lang="scss">
@import 'styles/main';
</style>
