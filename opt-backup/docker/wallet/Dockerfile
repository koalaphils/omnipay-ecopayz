FROM node:alpine as wallet
ENV SSH_USER www
ENV SSH_PASS admin.123
ENV WALLET_PORT 3000
RUN set -eux \
  && npm install --no-scripts --no-optional --prefer-dist \
    blockchain-wallet-service \
    xpub-scan \
  && apk add --update --no-cache openssh-server
RUN { \
  echo "#!/bin/env sh"; \
  echo "adduser -D \${SSH_USER}"; \
  echo "echo \"\${SSH_USER}:\${SSH_PASS}\" | chpasswd"; \
  echo "ssh-keygen -A"; \
  echo "`which sshd`"; \
  echo "\$(npm bin)/blockchain-wallet-service start -b 0.0.0.0 -p \${WALLET_PORT}"; \
  echo; \
  echo 'exec "$@"'; \
  } | tee /docker-entrypoint.sh
RUN { \
  echo "#!/bin/env sh"; \
  echo "export PATH=\$PATH:\$(npm bin):/node_modules/.bin"; \
} | tee /etc/profile.d/path.sh
RUN chmod a+x /docker-entrypoint.sh
ENTRYPOINT ["sh", "/docker-entrypoint.sh"]
