FROM python:3.11

RUN mkdir /app
WORKDIR /app
RUN pip install openai

COPY src .

RUN mkdir /api-key

CMD ["python", "./main.py"]