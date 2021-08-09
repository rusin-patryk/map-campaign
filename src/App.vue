<template>
    <div id="app" class="app-wrapper">
        <div class="header-container">
            <div class="container">
                <img draggable="false" src="@/assets/images/logo.png" alt="logo" style="height: 90px">
            </div>
        </div>
        <div class="content-container">
            <div class="container">
                <div class="page-title">
                    <div class="page-title-image">
                        <img src="@/assets/images/kontener.png" alt="biedronka">
                    </div>
                    <div class="page-title-content">
                        Wyszukaj&nbsp;najbliższy <span style="white-space: nowrap">sklep&nbsp;<img src="@/assets/images/biedronka.png" alt="biedronka"></span><br>
                        z&nbsp;naszym kontenerem<br>
                        i&nbsp;oddaj&nbsp;ubrania w&nbsp;których&nbsp;już nie&nbsp;chodzisz
                    </div>
                    <div class="page-title-logo">
                        <img draggable="false" src="@/assets/images/logo.png" alt="logo">
                    </div>
                </div>
                <div class="map-section">
                    <MapBox @pickedLocation="getPickedLocation" @closestPoint="getClosestPoint" />
                    <div class="map-section-tips">
                        <div class="arrow-up"></div>
                        <div v-if="!pickedLocation && !closestPoint.name">
                            Wyszukaj lokalizację poprzez formularz lub użyj swojej lokalizacji, aby znaleźć najbliższe sklepy z naszym kontenerem.
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
                <a href="/Polityka_prywatnosci-cookies_oddajubrania.pl.pdf" target="_blank">Polityka prywatności</a>
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

    mounted() {
        window.onbeforeunload = function(){
            const cookies = document.cookie.split(";");

            for (let i = 0; i < cookies.length; i++) {
                const cookie = cookies[i];
                const eqPos = cookie.indexOf("=");
                const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
                document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
            }
        };
    },

    methods: {
        getPickedLocation(data) {
            this.pickedLocation = data;
        },

        getClosestPoint(data) {
            this.closestPoint = data;
        },

        removeCookies() {
            const cookies = document.cookie.split(";");

            for (let i = 0; i < cookies.length; i++) {
                const cookie = cookies[i];
                const eqPos = cookie.indexOf("=");
                const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
                document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
            }
        }
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

<style lang="scss">
@import 'styles/main';
</style>
