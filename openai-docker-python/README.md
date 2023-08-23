
```
docker build -t openai-docker-python .;
docker run -v $(pwd)/openai-api-key.txt:/api-key/openai-api-key.txt -v $(pwd)/src:/app openai-docker-python
```