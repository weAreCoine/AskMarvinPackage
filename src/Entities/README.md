# Entities

Entities represent domain objects created at runtime.
They are not persisted in the database (although they may be cached for performance reasons).

Unlike Data Transfer Objects (DTOs), which are flat structures intended only to carry data across layers, Entities model
concepts of the application domain and may include minimal logic for validation or transformation.

Their primary purpose is to provide a stable abstraction that can be used independently of any concrete service
implementations (e.g., APIs, databases, or third-party integrations).

### DTO vs Entity

| Aspect          | DTO (e.g., `LangfusePrompt`)                                | Entity (e.g., `Prompt`)                                                   |
|-----------------|-------------------------------------------------------------|---------------------------------------------------------------------------|
| **Purpose**     | Represent the raw structure returned by an external service | Represent a business/domain concept that the application works with       |
| **Structure**   | Mirrors the external API fields                             | Encapsulates domain meaning, may expose richer methods or transformations |
| **Persistence** | Not persisted                                               | Not persisted (but may be cached)                                         |
| **Dependency**  | Tightly coupled to external provider (Langfuse API)         | Independent of provider; stable contract for the application              |
| **Example**     | `LangfusePrompt { id, name, label, version, content }`      | `PromptTemplate { name, version, content, render(vars) }`                 |