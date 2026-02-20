# Introduction

laminas-session provides a set of validators that provide protections against session hijacking and against unauthorized requests.

The following validators are intended to be used via the configuration of `Laminas\Session\SessionManager`:

- [Id](id.md)
- [Http User Agent](httpuseragent.md)
- [Remote Addr](remoteaddr.md)

The `Csrf` validator is based on Laminas component for validation of data and files: [laminas-validator](https://docs.laminas.dev/laminas-validator/).
As such, its usage differs from the other shipped validators:

- [Csrf](csrf.md)

> MISSING: **Installation Requirements**
> The validation support of laminas-session depends on the [laminas-validator](https://docs.laminas.dev/laminas-validator/) component, so be sure to have it installed before getting started:
>
> ```bash
> $ composer require laminas/laminas-validator
> ```
