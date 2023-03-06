export const state = () => ({
  loading: false
});

export const mutations = {
  showLoader(state, bool) {
    state.loading = bool;
  }
};
