## Installation and configuration steps
### Git
``git clone https://gitlab.com/trushPavel/englishtextadvicer.git``
### Docker
1. Create a docker/.env file, copy here content from docker/.env.example and put your data to variables.
2. Launch docker/docker-build.sh (Linux, Mac OS)
3. Go to .env. Set the next configs:
- Set your Open AI key ``OPENAI_API_KEY={YOUR_API_KEY}``. See how to get it: https://www.howtogeek.com/885918/how-to-get-an-openai-api-key/
- Set your telegram bot token ``TELEGRAM_BOT_TOKEN={YOUR_BOT_TOKEN}``. Visit this documentation to get it: https://core.telegram.org/bots/features#botfather
4. Add "english-adviser.me" to /etc/hosts

## Start using
### Botman
Botman works in your local environment. In order to use it put http://english-adviser.me.
Click on the widget in the bottom right corner 

### Telegram
If you want to test Telegram integration in the local environment, use ngrok to make your local project available outside. 
Set your ngrok url in .env: TELEGRAM_WEBHOOK_URL={YOUR_URL}
Afterward go to {YOUR_URL}/set-telegram-webhook to set webhook url
Now you can use your telegram bot.



