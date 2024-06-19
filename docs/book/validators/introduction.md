# Introduction

laminas-session provides a set of validators that provide protections against session hijacking and against unauthorized requests.

- [Http User Agent](httpuseragent.md)
- [Remote Addr](remoteaddr.md)
- [Writing Custom Validators](writing-custom-validators.md)

These validators are based on Laminas component for validation of data and files: [laminas-validator](https://docs.laminas.dev/laminas-validator/).

> MISSING: **Installation Requirements**
> The validation support of laminas-session depends on the [laminas-validator](https://docs.laminas.dev/laminas-validator/) component, so be sure to have it installed before getting started:
>
> ```bash
> $ composer require laminas/laminas-validator
> ```
