import pandas as pd

df = pd.read_excel('utilitarios/CAEB.xlsx')
with open('caeb_data.sql', 'w', encoding='utf-8') as f:
    for index, row in df.iterrows():
        codigo = str(row['CAEB']).replace("'", "''")
        desc = str(row['Descripcion']).replace("'", "''")
        f.write(f"INSERT INTO caeb_diccionario (codigo, descripcion) VALUES ('{codigo}', '{desc}');\n")
