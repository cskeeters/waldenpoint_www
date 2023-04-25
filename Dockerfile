FROM slim_app:latest

RUN apk add --no-cache gcc python3 python3-dev musl-dev
RUN pip install beancount

ADD init.sh /
RUN chmod 755 /init.sh
