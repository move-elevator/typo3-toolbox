import HttpService from './HttpService'
import * as Sentry from '@sentry/browser'

class SentryMonitoringService {
  init () {
    HttpService.get(window.location.origin + '/api/sentry').then((data) => {
        const { dsn, env, release } = data

        if (
          dsn === '' ||
          dsn === undefined
        ) {
          return
        }

        const sentryData = {
          dsn,
          integrations: [
            Sentry.browserTracingIntegration(),
          ],
          tracesSampleRate: 0.3
        }

        if (env !== '') {
          sentryData.environment = env
        }

        if (release !== '') {
          sentryData.release = release
        }

        Sentry.init(sentryData)
      }).catch((error) => {
        console.error('Sentry monitoring service error:', error)
      })
  }
}

export default SentryMonitoringService
