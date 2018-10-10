grep -q "INFO success: nginx entered RUNNING state" <(docker-compose logs -f --tail=1 bo)
