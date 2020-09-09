<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jan Evers <j.evers@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->

<template>
  <div>
    <color-picker theme="light" :color="color" @changeColor="changeColor" :colors-default="[]" />
  </div>
</template>

<script>
import colorPicker from '@caohenghu/vue-colorpicker'

export default {
  name: 'ColorPickerApp',
  components: {
    colorPicker
  },
  props: ['initialColor'],
  data () {
    return {
      color: '#90C0FF'
    }
  },
  methods: {
    changeColor (color) {
      this.color = color.hex
    },
    getColor () {
      return this.color
    }
  },
  beforeMount () {
    this.color = '#' + this.initialColor
  }
}
</script>

<style>
  div.color-alpha {
    display: none;
  }
  ul.colors {
    display: none;
  }
</style>
