import pandas as pd
import json

try:
    df = pd.read_excel('utilitarios/CAEB.xlsx')
    # Print columns
    print("Columns:", list(df.columns))
    # Print first 2 rows
    print("Head:\n", df.head(2).to_json(orient='records'))
except Exception as e:
    print("Error:", e)
