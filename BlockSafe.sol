// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

/// @notice Mencatat bukti hash dokumen. File asli tidak disimpan di blockchain.
contract BlockSafe {
    uint256 public documentCount;

    struct Document {
        bytes32 fileHash;
        address owner;
        uint256 timestamp;
        bool exists;
    }

    mapping(uint256 => Document) private documents;

    event DocumentStored(
        uint256 indexed documentId,
        bytes32 indexed fileHash,
        address indexed owner,
        uint256 timestamp
    );

    function storeDocument(bytes32 fileHash) external returns (uint256 documentId) {
        require(fileHash != bytes32(0), "Invalid hash");
        documentId = ++documentCount;
        documents[documentId] = Document({
            fileHash: fileHash,
            owner: msg.sender,
            timestamp: block.timestamp,
            exists: true
        });
        emit DocumentStored(documentId, fileHash, msg.sender, block.timestamp);
    }

    function getDocument(uint256 documentId)
        external view returns (bytes32 fileHash, address owner, uint256 timestamp, bool exists)
    {
        Document memory d = documents[documentId];
        return (d.fileHash, d.owner, d.timestamp, d.exists);
    }

    function verifyDocument(uint256 documentId, bytes32 candidateHash) external view returns (bool) {
        Document memory d = documents[documentId];
        return d.exists && d.fileHash == candidateHash;
    }
}