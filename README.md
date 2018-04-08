# PHP API Skeleton
Built using the Slim PHP Framework.

##### Controllers
- User (for example)
- OAuth2

##### Middleware
- API Header (used for API Keys or anything you want)
- OAuth2 (validates Authorization header containing an access token)
- Rate Limiting (using Redis)

##### Storage
- Database (uses PDO for MySQL)
- OAuth2 (uses Database)
- Redis (wrapper for Predis, used for Rate Limiting and Caching)

##### Utils
- General (for general helper functions)

##### Dependency Injection
Uses Slim's custom container service for dependency injection. [Docs](https://www.slimframework.com/docs/v3/concepts/di.html 'Slim Dependency Container')