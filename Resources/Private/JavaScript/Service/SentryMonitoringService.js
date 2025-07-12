import HttpService from './HttpService'
import * as Sentry from '@sentry/browser'

class SentryMonitoringService {
  init () {
    HttpService.get(window.location.origin + '/api/sentry').then((data) => {
      const { dsn, env, release } = data

      if (!dsn || dsn.trim() === '') {
        return
      }

      const sentryData = {
        dsn,
        integrations: [
          Sentry.browserTracingIntegration(),
        ],
        tracesSampleRate: 0.3
      }

      if (env && env.trim() !== '') {
        sentryData.environment = env
      }

      if (release && release.trim() !== '') {
        sentryData.release = release
      }

      Sentry.init(sentryData)
    }).catch((error) => {
      console.error('Sentry monitoring service error:', error)
    })
  }
}

const sentryMonitoringService = new SentryMonitoringService()
sentryMonitoringService.init()
