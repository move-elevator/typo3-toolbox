class HttpService {
  async get (url) {
    const response = await fetch(url, { method: 'GET' })

    return this.handleResponse(response)
  }

  async post (url, headers, body, signal) {
    const response = await fetch(url, {
      method: 'POST',
      headers,
      body,
      signal
    })

    return this.handleResponse(response)
  }

  async handleResponse (response) {
    const data = await response.json()

    if (!response.ok) {
      throw new Error(response.statusText)
    }

    return data
  }
}

export default new HttpService()
