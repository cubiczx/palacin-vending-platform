# palacin-vending-platform
🎓 Vending Machine Technical Challenge — Full-stack implementation with Symfony & React.

## Development environment

This project was developed and tested locally using [Laravel Herd](https://herd.laravel.com/) (PHP/Symfony) and a
native MongoDB installation on Windows — not Docker.

A `Dockerfile` and `docker-compose.yml` are included to make evaluation easier with a single command, as requested
in the challenge. However, due to virtualization limitations on the development machine, **the Docker setup could
not be tested end-to-end locally**. The configuration follows standard practices for this stack (PHP-FPM + Nginx,
Node build + Nginx static serving, MongoDB container) and should work as-is, but please report any issues if you
run into them when evaluating.
