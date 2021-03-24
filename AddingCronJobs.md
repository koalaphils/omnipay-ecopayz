
# How to add Console Command to Cron/Scheduled tasks
this system is using its own scheduling system to lessen the manual operations done when installing a new cron command
https://github.com/Cron/Symfony-Bundle


### to add a new console command to cron
```bash
# you only need to add your schedule and console-command at app/config/cron.yml
# you may use tools like https://crontab.guru to create your cron schedule
member.inactive-list.update:
    schedule: '15 6 * * *'
    console-command: 'member:inactive-list:update'

    
```



### Notes
you need to call cron:run every minute using the regular cron
```bash
* * * * * /path/to/symfony/install/app/console scheduler:run
```