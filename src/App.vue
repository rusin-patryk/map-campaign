<template>
    <div id="app">
        <MapBox msg="Welcome to Your Vue.js App" />
    </div>
</template>

<script>
import MapBox from './components/MapBox.vue';

export default {
    name: 'App',
    components: {
        MapBox,
    },
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
