import React, { useState } from "react";
import axios from "axios";

const VerificationComponent = () => {
    const [jsonFile, setJsonFile] = useState(null);
    const [result, setResult] = useState(null);
    const [error, setError] = useState(null);

    const handleFileChange = (e) => {
        const fileReader = new FileReader();
        fileReader.readAsText(e.target.files[0], "UTF-8");
        fileReader.onload = (e) => {
            setJsonFile(JSON.parse(e.target.result));
        };
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(null);
        setResult(null);

        if (!jsonFile) {
            setError("Please upload a JSON file first.");
            return;
        }

        try {
            const response = await axios.post(
                "http://localhost:8080/api/v1/verify",
                jsonFile,
                {
                    headers: {
                        "Content-Type": "application/json",
                    },
                }
            );
            setResult(response.data);
        } catch (err) {
            setError("Error verifying JSON. Please check your API and file format.");
            console.error(err);
        }
    };

    return (
        <div>
            <h1>JSON Verification</h1>
            <form onSubmit={handleSubmit}>
                <input type="file" accept=".json" onChange={handleFileChange} />
                <button type="submit">Verify JSON</button>
            </form>

            {result && (
                <div>
                    <h3>Verification Result</h3>
                    <p>Issuer: {result.data.issuer}</p>
                    <p>Result: {result.data.result}</p>
                </div>
            )}

            {error && (
                <div style={{ color: "red" }}>
                    <p>{error}</p>
                </div>
            )}
        </div>
    );
};

export default VerificationComponent;
