import React, { useState } from "react";
import axios from "axios";

const VerificationComponent = () => {
    const [jsonFile, setJsonFile] = useState(null);
    const [result, setResult] = useState(null);
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);

    const handleFileChange = (e) => {
        const fileReader = new FileReader();
        fileReader.readAsText(e.target.files[0], "UTF-8");
        fileReader.onload = (e) => {
            try {
                setJsonFile(JSON.parse(e.target.result));
            } catch (err) {
                setError("Error verifying JSON. Please check your file format.");
                console.error(err);
            }
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
            setLoading(true);
            const response = await axios.post(
                process.env.REACT_APP_VERIFY_API,
                jsonFile,
                {
                    headers: {
                        "Content-Type": "application/json",
                        "Authorization": "Bearer " + process.env.REACT_APP_AUTH_TOKEN,
                    },
                }
            );
            setResult(response.data);
        } catch (err) {
            setError("Error verifying JSON. Please check your API and file format.");
            console.error(err);
        }
        setLoading(false);
    };

    return (
        <div className="container">
            <h1>{process.env.REACT_APP_PAGE_TITLE}</h1>
            <form onSubmit={handleSubmit}>
                <div className="mb-3 row">
                    <div className="col-sm-10">
                        <input type="file" className="form-control" onChange={handleFileChange}/>
                    </div>
                </div>

                <div className="row">
                    <div className="col-sm-10 offset-sm-2">
                        <button type="submit" className="btn btn-primary">Verify JSON</button>
                    </div>
                </div>
            </form>
            {loading && <div className="spinner"></div>}

            {result && (
                <div>
                    <h3>Verification Result</h3>
                    <p>Issuer: {result.data.issuer}</p>
                    <p>Result: {result.data.result}</p>
                </div>
            )}

            {error && (
                <div style={{color: "red"}}>
                    <p>{error}</p>
                </div>
            )}
        </div>
    );
};

export default VerificationComponent;
