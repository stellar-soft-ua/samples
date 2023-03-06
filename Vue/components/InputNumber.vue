<template>
  <div class="c-inputNumber">
    <button :disabled="number <= minimal" class="pa-1 pa-sm-3 body-1 font-weight-bold" @click="handleButtonClick(-1)">
      <v-icon>mdi-minus</v-icon>
    </button>
    <input ref="input" v-model.number="number" class="body-1" @change="handleTextChange" />
    <button class="pa-1 pa-sm-3 body-1 font-weight-bold" :disabled="number > maximum" @click="handleButtonClick(+1)">
      <v-icon>mdi-plus</v-icon>
    </button>
  </div>
</template>

<script>
export default {
  props: {
    value: {
      default: 0,
      type: Number
    },
    minimal: {
      type: Number,
      default: 0
    },
    maximum: {
      type: Number,
      default: 1000
    }
  },
  data() {
    return {
      number: 0,
      inputRef: null
    };
  },
  mounted() {
    this.number = this.value;
    const inputRef = this.$refs.input;
    inputRef.addEventListener('keypress', (e) => {
      if (e.charCode < 48 || e.charCode > 57) {
        this.$toasted.global.error({
          message: this.atc('notices.onlyDigits')
        });
        inputRef.value = Math.abs(this.number);
      }
    });
  },
  methods: {
    handleButtonClick(number) {
      this.number += number;
      this.$emit('change', this.number);
    },
    handleTextChange() {
      this.hasErrors = false;
      this.validate();
      if (!this.hasErrors) {
        this.$emit('setInputValid', true);
        this.$emit('change', this.number);
      }
    },
    setInvalidState() {
      this.hasErrors = true;
      this.$emit('setInputValid', false);
    },
    validate() {
      if (this.number === '') {
        this.setInvalidState();
        return this.$toasted.global.error({
          message: `${this.atc('labels.quantity')} ${this.atc('notices.requiredError')}`
        });
      }
      if (this.number < this.minimal) {
        this.setInvalidState();
        return this.$toasted.global.error({
          message: this.atc('notices.minimalQuantity', `${this.minimal}`)
        });
      }
      if (this.number >= this.maximum) {
        this.setInvalidState();
        this.$toasted.global.error({
          message: this.atc('notices.maximumQuantity', `${this.maximum}`)
        });
      }
      if (typeof this.number === 'string') {
        this.setInvalidState();
      }
    }
  }
};
</script>

<style lang="scss">
.c-inputNumber {
  display: flex;
  max-width: 300px;
  height: 44px;
  input {
    width: 45px;
    text-align: center;
    font-weight: normal;
    border-top: 1px solid var(--v-primary-base);
    border-bottom: 1px solid var(--v-primary-base);

    @media (min-width: 960px) {
      width: 70px;
    }
  }
  button {
    display: flex;
    align-items: center;
    padding: 5px;
    background-color: #f8f8f9;
    border: 1px solid var(--v-primary-base);
  }
  input::-webkit-outer-spin-button,
  input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }
  input[type='number'] {
    -moz-appearance: textfield;
  }
}
</style>
