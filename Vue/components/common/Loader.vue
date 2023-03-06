<template>
    <div class="d-flex justify-center align-center loader" @scroll.prevent @wheel.prevent @touchmove.prevent>
        <v-progress-circular :size="70" :width="7" indeterminate color="primary"></v-progress-circular>
    </div>
</template>

<style lang="scss">
.loader {
    width: 100%;
    position: fixed;
    top: 0;
    left: 0;
    background-color: rgba(255, 255, 255, 0.5);
    height: 100vh;
    overflow: hidden;
    z-index: 9999999;

    div {
        z-index: 9999999;
    }
}
</style>
