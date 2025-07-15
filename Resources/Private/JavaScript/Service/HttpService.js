class HttpService {
  async get (url, options = {}) {
    const response = await fetch(url, {
      method: 'GET',
      ...options
    })

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
    if (!response.ok) {
      let errorMessage = response.statusText

      try {
        const errorData = await response.json()
        errorMessage = errorData.message || errorMessage
      } catch {
        // If JSON parsing fails, use statusText
      }

      throw new Error(`HTTP ${response.status}: ${errorMessage}`)
    }

    try {
      return await response.json()
    } catch (error) {
      throw new Error('Invalid JSON response')
    }
  }
}

export default new HttpService()
