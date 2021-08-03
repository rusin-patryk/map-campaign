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
.location-picker {
    position: fixed;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 100vh;
    overflow-x: hidden;
    overflow-y: auto;
    background-color: rgba(0, 0, 0, .66);
    z-index: 1000;
    top: 0;
    left: 0;

    & > div {
        position: relative;
        box-sizing: border-box;
        width: auto;
        max-width: calc(100% - 20px);
        z-index: 1001;
        background-color: white;
        padding: 30px;
        text-align: left;
        border-radius: 30px;
        box-shadow: 0 0 100px black;

        & > span {
            display: block;
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        & > div {
            padding: 10px;
            transition-duration: .2s;

            &:hover {
                background-color: rgba(0, 0, 0, .1);
            }
        }

        & > button {
            padding: 15px 30px;
            color: white;
            background-color: #C00C64;
            border-radius: 15px;
            margin: 25px auto 0 auto;
            border: none;
            text-transform: uppercase;
            font-weight: 500;
            font-size: 18px;
            display: block;
            cursor: pointer;
            transition-duration: .2s;

            &:hover {
                background-color: #9b0a51;
            }
        }

        & > hr {
            border: 1px solid #ccc;
            border-bottom: none;
        }
    }
}

.strong {
    font-weight: bold;
}
</style>
