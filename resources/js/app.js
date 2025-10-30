require('./bootstrap');

// Vue setup
window.Vue = require('vue');

// import global fake-sms css so mix picks it up if desired
try{ require('../css/fake-sms.css'); }catch(e){}

// register component
Vue.component('fake-sms', require('./components/FakeSms.vue').default);

const app = new Vue({
	el: '#app'
});
