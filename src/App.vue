<template>
    <div id="app">
        <MapBox @pickedLocation="getPickedLocation" @closestPoint="getClosestPoint" />
        <div v-if="pickedLocation">
            {{ pickedLocation }}
        </div>
        <div v-if="closestPoint.name">
            {{ closestPoint.name }}
            <a :href="`https://www.google.com/maps/dir//${closestPoint.latLng[0]},${closestPoint.latLng[1]}`"
               target="_blank">Nawiguj</a>
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
@import '~leaflet.markercluster/dist/MarkerCluster.css';
@import '~leaflet.markercluster/dist/MarkerCluster.Default.css';

body {
    margin: 0;
}

#app {
    font-family: Avenir, Helvetica, Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-align: center;
    color: #2c3e50;
    margin-top: 60px;
}
</style>
