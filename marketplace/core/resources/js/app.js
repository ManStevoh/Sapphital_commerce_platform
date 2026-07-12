import { createApp } from 'vue'
import axios from "axios";
//load components
import {getCurrencySymbolWithAmount} from "./vue/helpers";

import TopHeader from "./vue/components/header/Header.vue";
import CartItems from "./vue/components/sidebar/CartItems.vue";

import AppLayout from "./vue/layouts/app.vue";

const app = createApp(AppLayout)
// global helper for all vue component
// plugin method load or initial globally

app.mixin({
    methods:{
        getCurrencySymbolWithAmount
    }
});

app.config.globalProperties.currencySymbol = window.currencySymbol;

app.component('TopHeader', TopHeader);
app.component('CartItems', CartItems);
app.mount('#app');
