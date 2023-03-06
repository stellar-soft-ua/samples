import request from '~/utils/request.js'

export const state = () => ({
  collections: [],
  collectionProducts: [],
  availableCollections: null,
  activeCollection: null,
  disableProductAllTab: false,

  collectionsLoaded: false,
})

export const mutations = {
  setActiveCollection(state, val) {
    if (val) {
      state.activeCollection = { ...val }
    } else {
      state.activeCollection = false
    }
  },
  setDefaultActiveCollection(state, defaultActiveCollection) {
    state.defaultActiveCollection = defaultActiveCollection
  },
  setCollections(state, val) {
    state.collections = val
    state.collectionsLoaded = true
  },
  setCollectionProducts(state, val) {
    state.collectionProducts = [...val.data]
  },
  setAvailableCollections(state, availableCollections) {
    state.availableCollections = availableCollections
  },
  setDisableProductAllTab(state, val) {
    state.disableProductAllTab = val
  }
}

export const actions = {
  LIST_COLLECTIONS({ commit, rootState }) {
    const { storeDomain } = rootState.api

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/collections`,
      })
        .then((response) => {
          commit('setCollections', response.data.data)
          resolve(response.data)
        })
        .catch((error) => {
          console.error('LIST_PRODUCTS error: ', error)
          reject(error)
        })
    })
  },

  GET_COLLECTION_BY_ID({ rootState }, id) {
    const { storeDomain } = rootState.api

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/collections/${id}`,
      })
        .then((response) => {
          resolve(response.data)
        })
        .catch((error) => {
          console.error(`GET_COLLECTION_BY_ID ${id} error: `, error)
          reject(error)
        })
    })
  },
}
