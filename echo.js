require('dotenv').config()

const { env } = process

require('laravel-echo-server').run({
  authHost: env.APP_URL,
  authEndpoint: '/broadcasting/auth',
  devMode: true,
  database: 'redis',
  databaseConfig: {
    redis: {
      host: env.REDIS_HOST,
      port: env.REDIS_PORT,
    },
  },
  apiOriginAllow: {
    allowCors: true,
    allowOrigin: 'http://127.0.0.1',
    allowMethods: 'GET, POST',
    allowHeaders: 'Origin, Content-Type, X-Auth-Token, X-Requested-With, Accept, Authorization, X-CSRF-TOKEN, X-Socket-Id',
  },
});
