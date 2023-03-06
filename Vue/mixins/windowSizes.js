import throttle from 'lodash.throttle'

export const windowSizes = {
  data: () => {
    return {
      windowHeight: 0,
      windowWidth: 0,
    }
  },

  created() {
    // set resize listener
    window.addEventListener('resize', throttle(this.setWindowSizes, 100))
    this.setWindowSizes()
  },

  methods: {
    setWindowSizes: function() {
      this.windowWidth = document.documentElement.clientWidth
      this.windowHeight = document.documentElement.clientHeight
    },
  },
}
