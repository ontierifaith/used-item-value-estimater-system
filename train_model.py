import pandas as pd
import psycopg2
from sklearn.ensemble import RandomForestRegressor
import joblib

# 🔌 Database connection
conn = psycopg2.connect(
    database="used_item_value_estimator",
    user="postgres",
    password="BQfa2050*",
    host="localhost"
)

# 📥 Load table
df = pd.read_sql("SELECT * FROM training_data", conn)
conn.close()

# 🧹 Clean missing values
df = df.fillna(0)

# 🎯 Features (inputs)
X = df[['scraped_min', 'scraped_max', 'scraped_avg', 'item_age', 'condition']]

# 🎯 Target (output the model predicts)
y = df['scraped_avg']

# 🤖 Random Forest Model
model = RandomForestRegressor(
    n_estimators=250,
    max_depth=12,
    random_state=42
)

# 🚀 Train model
model.fit(X, y)

# 💾 Save trained model
joblib.dump(model, "model.pkl")

print("MODEL TRAINED & SAVED SUCCESSFULLY!")
