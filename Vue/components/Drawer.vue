<script>
export default {
  props: {
    show: {
      type: Boolean,
      default: false
    },
    title: {
      type: [String, Boolean],
      default: false
    }
  },
  watch: {
    show(val) {
      if (val) {
        if (window) {
          const location = this.$refs.drawer.$el.scrollTop;
          window.scroll(0, location);
        }
      }
      if (!val) {
        this.$emit('close');
      }
    }
  },
  mounted() {
    if (window) {
      const location = this.$refs.drawer.scrollTop;
      window.scroll(0, location);
    }
  }
};
</script>

<template>
  <v-navigation-drawer ref="drawer" v-model="show" fixed width="450px" right temporary>
    <v-card tile height="100%">
      <v-toolbar flat color="primary">
        <v-btn icon @click="$emit('close')">
          <v-icon class="white--text">mdi-close</v-icon>
        </v-btn>
        <v-toolbar-title v-if="title" class="white--text">{{ title }}</v-toolbar-title>
        <v-spacer></v-spacer>
        <slot name="button" />
      </v-toolbar>
      <v-card-text align="center" height="100%">
        <slot />
      </v-card-text>
    </v-card>
  </v-navigation-drawer>
</template>
