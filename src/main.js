import Vue from 'vue';
import App from './App.vue';
import axios from 'axios'
import VueAxios from 'vue-axios'
import 'leaflet/dist/leaflet.css';

// eslint-disable-next-line
delete L.Icon.Default.prototype._getIconUrl;
// eslint-disable-next-line
L.Icon.Default.mergeOptions({
    iconRetinaUrl: require('./assets/marker-icon.png'),
    iconSize: [28, 41],
    iconUrl: require('./assets/marker-icon.png'),
    shadowUrl: require('leaflet/dist/images/marker-shadow.png'),
});

Vue.config.productionTip = false;
Vue.use(VueAxios, axios);

new Vue({
    render: (h) => h(App),
}).$mount('#app');
