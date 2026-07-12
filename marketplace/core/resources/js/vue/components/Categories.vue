<template>
  <div>
    <Select2 v-model="myValue" :options="myOptions" :settings="{ settingOption: value, settingOption: value }" @change="myChangeEvent($event)" @select="mySelectEvent($event)" />
    <h4>Value: {{ myValue }}</h4>
  </div>
</template>

<script>
export default {
  name: "Categories",
  components: {
  },
  setup: () => {

    const myValue = ref(0);
    const myOptions =  ['op1', 'op2', 'op3'];

    function myChangeEvent(value){

    }
    function mySelectEvent(value){

    }
    return {
      myValue,
      myOptions,
      myChangeEvent
    }

  }
}
</script>

<style scoped>

</style>