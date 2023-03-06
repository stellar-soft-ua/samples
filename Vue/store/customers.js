import request from '~/utils/request.js'

export const state = () => ({
  orders: [],
  customer: {}
})

export const mutations = {
  setCustomer(state, val) {
    state.customer = { ...val }
  },

  setOrders(state, val) {
    state.orders = [...val.data]
  },
}

export const actions = {
  CUSTOMER_GET_ME({ commit, rootState }, id) {
    const { storeDomain } = rootState.api

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/me`,
      })
        .then((response) => {
          commit('setCustomer', response.data.data)
          resolve(response.data.data)
        })
        .catch((error) => {
          console.error(`CUSTOMER_GET_ME ${id} error: `, error)
          reject(error)
        })
    })
  },

  CUSTOMER_GET_ORDERS({ commit, rootState }, id) {
    const { customer } = state
    const { storeDomain } = rootState.api

    let customerId = false
    if (customer) {
      customerId = customer.id
    } else if (id) {
      customerId = id
    } else {
      console.error('No id available: CUSTOMER_GET_ORDERS')
    }

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/customers/${customerId}/orders`
      })
        .then((response) => {
          resolve(response.data)
          commit('setOrders', response.data)
        })
        .catch((error) => {
          console.error(`CUSTOMER_GET_ORDERS ${id} error: `, error)
          reject(error)
        })
    })
  },

  CUSTOMER_GET_REFUNDS({ commit, rootState }, id) {
    const { storeDomain } = rootState.api

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/customers/${id}/refunds`
      })
        .then((response) => {
          resolve(response.data)
        })
        .catch((error) => {
          console.error(`CUSTOMER_GET_REFUNDS ${id} error: `, error)
          reject(error)
        })
    })
  },
}
