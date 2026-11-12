// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

contract DocumentRegistry {

    address public owner;

    struct Document {
        bytes32 documentHash;
        address registeredBy;
        uint256 timestamp;
        bool exists;
    }

    mapping(string => Document) private documents;

    event DocumentRegistered(
        string indexed documentId,
        bytes32 documentHash,
        address indexed registeredBy,
        uint256 timestamp
    );

    modifier onlyOwner() {
        require(msg.sender == owner, "Unauthorized");
        _;
    }

    constructor() {
        owner = msg.sender;
    }

    function registerDocument(
        string memory documentId,
        bytes32 documentHash
    ) public onlyOwner {

        require(bytes(documentId).length > 0, "Invalid ID");
        require(!documents[documentId].exists, "Already registered");
        require(documentHash != bytes32(0), "Invalid hash");

        documents[documentId] = Document({
            documentHash: documentHash,
            registeredBy: msg.sender,
            timestamp: block.timestamp,
            exists: true
        });

        emit DocumentRegistered(
            documentId,
            documentHash,
            msg.sender,
            block.timestamp
        );
    }

    function verifyDocument(
        string memory documentId,
        bytes32 documentHash
    ) public view returns (bool) {

        Document memory doc = documents[documentId];

        return (
            doc.exists &&
            doc.documentHash == documentHash
        );
    }

    function getDocument(
        string memory documentId
    ) public view returns (
        bytes32,
        address,
        uint256,
        bool
    ) {

        Document memory doc = documents[documentId];

        return (
            doc.documentHash,
            doc.registeredBy,
            doc.timestamp,
            doc.exists
        );
    }
}