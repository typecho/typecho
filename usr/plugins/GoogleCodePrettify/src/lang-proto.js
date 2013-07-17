// Copyright (C) 2006 Google Inc.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.


/**
 * @fileoverview
 * Registers a language handler for Protocol Buffers as described at
 * http://code.google.com/p/protobuf/.
 *
 * Based on the lexical grammar at
 * http://research.microsoft.com/fsharp/manual/spec2.aspx#_Toc202383715
 *
 * @author mikesamuel@gmail.com
 */

PR.registerLangHandler(PR.sourceDecorator({
        keywords: (
            'bool bytes default double enum extend extensions false fixed32 '
            + 'fixed64 float group import int32 int64 max message option '
            + 'optional package repeated required returns rpc service '
            + 'sfixed32 sfixed64 sint32 sint64 string syntax to true uint32 '
            + 'uint64'),
        cStyleComments: true
      }), ['proto']);
