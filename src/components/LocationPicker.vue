<template>
    <div v-if="locations.length" class="location-picker" @click="close">
        <div>
            <span>Znaleźliśmy kilka lokalizacji, wybierz jedną z listy:</span>
            <hr>
            <div v-for="(location, index) in locations"
                 :key="index"
                 @click="pickLocation(location)"
                 :class="{'strong': index === 0}"
                 style="cursor: pointer">
                {{ index + 1 }}. {{ location.place_name }}
            </div>
            <hr>
            <button type="button" @click="pickLocation(locations[0])">Wybierz za mnie!</button>
        </div>
    </div>
</template>

<script>
export default {
    name: "LocationPicker",
    props: {
        locations: Array,
    },

    methods: {
        pickLocation(location) {
            this.$emit('pickLocation', location);
        },

        close(element) {
            if (element.path[0].classList[0] === 'location-picker') {
                this.$emit('close');
            }
        },
    },
};
</script>

<style scoped lang="scss">
@import '../styles/_LocationPicker.scss';
</style>
