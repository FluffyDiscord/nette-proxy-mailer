
### extensions.neon

```neon
extensions:
    # needs to be last
    proxyMailer: ProxyMailer\Extension\ProxyMailerExtension

proxyMailer:
    endpoint: 'required, full URL'
    basic_auth_user_password: 'username:password'
    referer: 'https://mydomain.com'
    
    #optional
    host: ''
    username: ''
    password: ''
    security: ''
    port: ''
```