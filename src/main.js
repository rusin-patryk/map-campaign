import Vue from 'vue';
import App from './App.vue';
import axios from 'axios';
import VueAxios from 'vue-axios';
import 'leaflet/dist/leaflet.css';
import Notifications from 'vue-notification';
import VueSmoothScroll from 'vue2-smooth-scroll'

Vue.use(Notifications);
Vue.use(VueSmoothScroll);

// eslint-disable-next-line
delete L.Icon.Default.prototype._getIconUrl;
// eslint-disable-next-line
L.Icon.Default.mergeOptions({
    iconRetinaUrl: require('./assets/images/marker-icon.png'),
    iconSize: [28, 41],
    iconUrl: require('./assets/images/marker-icon.png'),
    shadowUrl: require('leaflet/dist/images/marker-shadow.png'),
});

Vue.config.productionTip = false;
Vue.use(VueAxios, axios);

new Vue({
    render: (h) => h(App),
}).$mount('#app');
