
from pathlib import Path
import json
import os
import sys

import openai
prompt = os.environ.get("PROMPT", None)
if prompt == 'answers-to-result':
    import main2
    sys.exit()

if prompt != 'email-to-answers':
    raise ValueError(f'Unknown prompt: {prompt}')

print("------ openai-docker-python ------")
txt = Path('/api-key/openai-api-key.txt').read_text()

inputTxt = Path('/input.txt').read_text()
openai.api_key = txt


#print ("Models")
#models = openai.Model.list()
#for model in models.data:
#    print(f" - {model.id}")


print ("Chat completion")
messages=[

    {"role": "system", "content": """

You are given emails and documents that might or might not contain answers to a questionnaire.

You only output if you find the answers.

    """},
    {"role": "user", "content": """

The following questions was asked:

1): Vil kommunen foreta maskinell eller manuell endelig telling av valgresultatet?

2): I hvilken politisk sak ble dette vedtatt?

3): Ved foreløpig opptelling (manuell opptelling), blir opptellingen gjort i valgkretsene? Hvis det er opptelling i valgkretsene, hvordan overfører kommunen resultatet fra valgkretsene inn til valgstyret/valgansvarlig/EVA Admin?

4): Ved foreløpig opptelling (manuell opptelling), hvordan lagrer/arkiverer kommunen resultatet fra opptellingen utenom EVA Admin? Papir? Digitalt dokument? SMS? Blir resultatet journalført?

5): Har kommunen rutiner for å kontrollere resultatet av foreløpig opptelling opp mot det som er synlig på valgresultat-siden til Valgdirektoratet (valgresultat dått no), i valgprotokoll, i medier og lignende?
En slik kontroll vil f.eks. oppdage tastefeil (kommunen legger inn feil resultat i EVA Admin) samt feil i Valgdirektoratets håndtering av resultatet.

6): Tilsvarende som 4) for endelig opptelling (maskinell eller manuell).
Hvordan lagrer kommunen resultatet fra endelig opptelling utenom i EVA Admin/EVA Skanning? Blir resultatet journalført?

7): Tilsvarende som 5) for endelig opptelling (maskinell eller manuell).
Har kommunen rutiner for kontroll av endelig opptelling mot resultat som blir publisert?

"""},
    {"role": "user", "content": """
The following email or document was given:

""" + inputTxt + """

"""},
    {"role": "user", "content": """
    Output as JSON.
    
    Example:
    
     Example JSON:
     {
        "any_answers_found": true/false,
        "answer1": "...",
        "answer2": "...",
        "answer3": "...",
        "answer4": "...",
        "answer5": "...",
        "answer6": "...",
        "answer7": "..."
     }
    
No translation of the answer. No changes. 
"""},

]
print("--------------- INPUT")
print(json.dumps(messages, indent=4))

chat_completion = openai.ChatCompletion.create(model="gpt-3.5-turbo-16k", messages=messages)

print("--------------- OUTPUT")
print(chat_completion)
print("---------------")
print(chat_completion.choices[0].message.content)
