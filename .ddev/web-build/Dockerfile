ARG BASE_IMAGE
FROM $BASE_IMAGE

RUN mkdir -p /var/www/html/v13/public/typo3 \
 && printf '%s' '<h1>Perform this first</h1> <code>ddev install-v13</code>' > /var/www/html/v13/public/index.html \
 && printf '%s' '<h1>Perform this first</h1> <code>ddev install-v13</code>' > /var/www/html/v13/public/typo3/index.html

ARG uid
ARG gid
RUN chown -R $uid:$gid /var/www/html/v13
